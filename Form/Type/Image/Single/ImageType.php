<?php
/**
 * Créé par Aropixel @2017.
 * Par: Joël Gomez Caballe
 * Date: 13/02/2017 à 17:15
 */

namespace Aropixel\AdminBundle\Form\Type\Image\Single;


use Aropixel\AdminBundle\Entity\Image;
use Aropixel\AdminBundle\Entity\ImageInterface;
use Aropixel\AdminBundle\Form\Type\EntityHiddenType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ImageType extends AbstractType implements DataMapperInterface
{

    /**
     * @var EntityManagerInterface
     */
    private $em;


    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['data_type']=='entity') {

            $imageOptions = array('class' => ImageInterface::class);
            if ($options['required']) {
                $imageOptions['required'] = true;
            }

            $builder
                ->add('image', EntityHiddenType::class, $imageOptions)
                ->add('title', HiddenType::class)
                ->add('alt', HiddenType::class)
            ;

            if ($options['crop_class']) {

                $builder
                    ->add('crops', CropsType::class, array(
                        'entry_options'  => array(
                            'image_class'  => $options['data_class'],
                            'data_class'  => $options['crop_class'],
                        )
                    ))
                ;

            }

        }
        elseif ($options['data_type']=='file_name') {

            $imageOptions = array();
            if ($options['required']) {
                $imageOptions['required'] = true;
            }

            $builder
                ->add('file_name', HiddenType::class, $imageOptions)
                ->setDataMapper($this)
            ;

        }
    }


    public function mapDataToForms($data, $forms)
    {
        // there is no data yet, so nothing to prepopulate
        if (null === $data) {
            return;
        }

        // invalid data type
        if (!is_string($data)) {
            throw new Exception\UnexpectedTypeException($data, 'string');
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);
        $forms['file_name']->setData($data);

    }


    public function mapFormsToData($forms, &$data)
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $data = $forms['file_name']->getData();

    }


    /**
     * Pass the image URL to the view
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $data = $form->getData();

        if ($options['data_type']=='entity') {

            $imageUrl = null;
            if (null !== $data) {
                $imageUrl = $data->getWebPath();
            }

            // set an "image_url" variable that will be available when rendering this field
            $view->vars['image_url'] = $imageUrl;
            $view->vars['attachedImage'] = $data;
            $view->vars['imageLongClass'] = $options['data_class'];

        }
        elseif ($options['data_type']=='file_name') {

            $view->vars['file_name'] = $data;
            $view->vars['path_file'] = Image::getFileNameWebPath($data);

        }

        $view->vars['data_type'] = $options['data_type'];
        $view->vars['attach_class'] = $form->getConfig()->getDataClass();
        $view->vars['library'] = $options['library'] ?: ($form->getConfig()->getDataClass() ? $form->getConfig()->getDataClass() : '');
        $view->vars['card_footer'] = $options['card_footer'];

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_type' => 'entity',
            'data_class' => null,
            'crop_class' => null,
            'library' => null,
            'card_footer' => true,
        ));
    }


    public function getBlockPrefix()
    {
        return 'aropixel_admin_image';
    }
}
